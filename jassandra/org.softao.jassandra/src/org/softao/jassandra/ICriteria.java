/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra;

import java.util.List;
import java.util.NavigableMap;

/**
 * The Interface ICriteria.
 */
public interface ICriteria {
	/**
	 * Specifies the token range of the criteria.
	 * <p>
	 * <b>Limitations</b>: currently, it's only used for {@link #select()} with
	 * single key or key range. If querying with more than one key, specified by
	 * {@link #keyList(List)} or {@link #keyList(String...)}, this does not used
	 * event set.
	 * 
	 * @param start
	 *            the start token, <code>null</code> means not specified.
	 * @param end
	 *            the end token, <code>null</code> means not specified
	 * @return this criteria
	 * @see #select()
	 * @throws JassandraException
	 */
	ICriteria tokenRange(String start, String end) throws JassandraException;

	/**
	 * Sets keys of the criteria.
	 * 
	 * @param keys
	 *            the list of keys
	 * @return this criteria
	 * @see #keyRange(String, String, int)
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	ICriteria keyList(List<String> keys) throws JassandraException;

	/**
	 * Sets the keys of the criteria.
	 * 
	 * @param keys
	 * @return this criteria
	 * @throws JassandraException
	 */
	ICriteria keyList(String... keys) throws JassandraException;

	/**
	 * Sets the key range
	 * 
	 * @param start
	 * @param end
	 * @param count
	 * @return this criteria
	 * @throws JassandraException
	 */
	ICriteria keyRange(String start, String end, int count)
			throws JassandraException;

	/**
	 * Currently, it's only used for delete on super column family.
	 * <p>
	 * This is only valid for super column family. While this is set, the column
	 * settings (range or list of keys) are specified for standard columns
	 * within this column family), otherwise, if this is not set, the column
	 * settings will be referring the super columns. See <a href=
	 * "http://code.google.com/p/jassandra/wiki/SlicePredicateAndColumnParent">
	 * SlicePredicateAndColumnParent</a> for details.
	 * 
	 * @param superColumnName
	 * @return this criteria
	 * @throws JassandraException
	 */
	ICriteria superColumn(ByteArray superColumnName) throws JassandraException;

	/**
	 * Sets the column list to be returned.
	 * <p>
	 * For standard column family, it's setting the columns returned. For super
	 * column family, if {@link #superColumn(ByteArray)} has set, the column
	 * settings (range or list of keys) are specified for standard columns
	 * within this column family), otherwise, the column settings will be
	 * referring the super columns. See <a href=
	 * "http://code.google.com/p/jassandra/wiki/SlicePredicateAndColumnParent">
	 * SlicePredicateAndColumnParent</a> for details.
	 * 
	 * @param columnNames
	 * @return this criteria
	 * @throws JassandraException
	 */
	ICriteria columnList(List<ByteArray> columnNames) throws JassandraException;

	/**
	 * Sets the column range to be returned.
	 * <p>
	 * For standard column family, it's setting the columns returned. For super
	 * column family, if {@link #superColumn(ByteArray)} has set, the column
	 * settings (range or list of keys) are specified for standard columns
	 * within this column family), otherwise, the column settings will be
	 * referring the super columns. See <a href=
	 * "http://code.google.com/p/jassandra/wiki/SlicePredicateAndColumnParent">
	 * SlicePredicateAndColumnParent</a> for details.
	 * 
	 * @param startColumName
	 * @param endColumnName
	 * @param count
	 * @return this criteria
	 * @throws JassandraException
	 */
	ICriteria columnRange(ByteArray startColumName, ByteArray endColumnName,
			int count)
			throws JassandraException;

	/**
	 * Sets the result in reversed order. Currently, it's only valid for
	 * querying with range (maybe refactor this one into the
	 * {@link #columnRange(ByteArray, ByteArray, int)} as a parameter.
	 * 
	 * @return this criteria
	 * @throws JassandraException
	 */
	ICriteria reverse() throws JassandraException;

	/**
	 * Selects the columns from the store.
	 * 
	 * @return key to columns map
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	NavigableMap<String, List<IColumn>> select() throws JassandraException;

	/**
	 * Deletes the columns which meetings the criteria.
	 * <p>
	 * For deleting, token not used, and only a single key can be specified.
	 * 
	 * @throws JassandraException
	 *             if more than one keys specified.
	 */
	void delete() throws JassandraException;

	/**
	 * Counts the columns can be returned from the criteria.
	 * 
	 * @return the number of columns for the specified criteria
	 * @throws JassandraException
	 */
	int count() throws JassandraException;
}
