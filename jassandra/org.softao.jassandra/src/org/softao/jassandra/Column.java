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

import java.util.ArrayList;
import java.util.List;

import com.google.common.collect.ImmutableList;

/**
 * The Class SimpleColumn.
 */
public class Column implements IColumn {
	
	/** The m name. */
	private ByteArray mName;
	
	/** The m timestamp. */
	private long mTimestamp;
	
	/** The m value. */
	private ByteArray mValue;
	
	/** The m columns. */
	private List<IColumn> mColumns;
	
	/** The m is super. */
	private final boolean mIsSuper;

	/**
	 * Instantiates a new simple column.
	 * 
	 * @param isSuper
	 *            the is super
	 */
	public Column(boolean isSuper) {
		mIsSuper = isSuper;
		mTimestamp = System.currentTimeMillis();
		if (isSuper) {
			mColumns = new ArrayList<IColumn>();
		}
	}

	/**
	 * Instantiates a new simple column.
	 * 
	 * @param name
	 *            the name
	 * @param value
	 *            the value
	 * @param timestamp
	 *            the timestamp
	 */
	public Column(ByteArray name, ByteArray value, long timestamp) {
		this(false);
		mName = name;
		mValue = value;
		mTimestamp = timestamp;
	}

	/**
	 * Instantiates a new simple column.
	 * 
	 * @param name
	 *            the name
	 * @param columns
	 *            the columns
	 */
	public Column(ByteArray name, List<IColumn> columns) {
		this(true);
		mName = name;
		mColumns.addAll(columns);
	}

	/**
	 * Gets the columns.
	 * 
	 * @return the columns
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	@Override
	public List<IColumn> getColumns() throws JassandraException {
		if (!isSuper()) {
			throw new JassandraException(
					"Only super column supports getColumns.");
		}

		ImmutableList.Builder<IColumn> builder = ImmutableList.builder();
		builder.addAll(mColumns);
		return builder.build();
	}

	/**
	 * Adds the columns.
	 * 
	 * @param columns
	 *            the columns
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	public void addColumns(IColumn... columns) throws JassandraException {
		if (!isSuper()) {
			throw new JassandraException(
					"Only super column supports addColumns.");
		}

		if (columns == null || columns.length == 0) {
			return;
		}
		for (IColumn column : columns) {
			mColumns.add(column);
		}
	}

	/**
	 * Gets the name.
	 * 
	 * @return the name
	 */
	@Override
	public ByteArray getName() {
		return mName;
	}

	/**
	 * Sets the name.
	 * 
	 * @param value
	 *            the new name
	 */
	public void setName(ByteArray value) {
		mName = value;
	}

	/**
	 * Gets the timestamp.
	 * 
	 * @return the timestamp
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	@Override
	public long getTimestamp() throws JassandraException {
		return mTimestamp;
	}

	/**
	 * Sets the timestamp.
	 * 
	 * @param value
	 *            the new timestamp
	 */
	public void setTimestamp(long value) {
		mTimestamp = value;
	}

	/**
	 * Gets the value.
	 * 
	 * @return the value
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	@Override
	public ByteArray getValue() throws JassandraException {
		if (isSuper()) {
			throw new JassandraException(
					"Super column doesnot support getValue.");
		}
		return mValue;
	}

	/**
	 * Sets the value.
	 * 
	 * @param value
	 *            the new value
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	public void setValue(ByteArray value) throws JassandraException {
		if (isSuper()) {
			throw new JassandraException(
					"Super column doesnot support setValue.");
		}

		mValue = value;
	}

	/**
	 * Checks if is super.
	 * 
	 * @return true, if is super
	 */
	@Override
	public boolean isSuper() {
		return mIsSuper;
	}

}
