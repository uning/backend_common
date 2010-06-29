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

/**
 * The Interface IColumn.
 */
public interface IColumn {
	
	/**
	 * Checks if is super.
	 * 
	 * @return if this is a super column
	 */
	boolean isSuper();

	/**
	 * Gets the name.
	 * 
	 * @return name of the column
	 */
	ByteArray getName();

	/**
	 * Gets the value.
	 * 
	 * @return value of the column
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	ByteArray getValue() throws JassandraException;

	/**
	 * Gets the timestamp.
	 * 
	 * @return timestamp of the column
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	long getTimestamp() throws JassandraException;

	/**
	 * Gets the columns.
	 * 
	 * @return the columns
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	List<IColumn> getColumns() throws JassandraException;

}
